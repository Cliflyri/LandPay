<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\ClientChangeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
class ClientChangeRequestController extends Controller {
 public function show(ClientChangeRequest $changeRequest): View {$changeRequest->load(['client','portalAccount']);return view('admin.client-change-requests.show',compact('changeRequest'));}
 public function apply(Request $request,ClientChangeRequest $changeRequest): RedirectResponse {$this->resolve($request,$changeRequest,true);return redirect()->route('admin.clients.show',$changeRequest->client_id)->with('success','Client contact changes applied.');}
 public function reject(Request $request,ClientChangeRequest $changeRequest): RedirectResponse {$this->resolve($request,$changeRequest,false);return redirect()->route('admin.dashboard')->with('success','Client contact changes rejected.');}
 private function resolve(Request $request,ClientChangeRequest $changeRequest,bool $apply): void {abort_unless($changeRequest->status==='pending',422);$note=$request->validate(['admin_note'=>['nullable','string','max:500']])['admin_note']??null;DB::transaction(function()use($request,$changeRequest,$apply,$note){if($apply){$values=collect($changeRequest->changes)->mapWithKeys(fn($change,$field)=>[$field=>$change['to']])->all();$values['updated_by_user_id']=$request->user()->id;$changeRequest->client()->update($values);if(isset($values['email'])&&$changeRequest->client->portalAccount)$changeRequest->client->portalAccount->update(['email'=>$values['email']]);}$changeRequest->update(['status'=>$apply?'applied':'rejected','reviewed_by_user_id'=>$request->user()->id,'reviewed_at'=>now(),'admin_note'=>$note]);$changeRequest->notices()->whereNull('dismissed_at')->update(['dismissed_by_user_id'=>$request->user()->id,'dismissed_at'=>now()]);});}
}
