<?php
namespace App\Http\Controllers;

use App\Models\{AdminNotice,Improvement,ImprovementUpdate,PaymentPlan};
use App\Services\SharedDocumentStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Storage};
use Illuminate\Validation\Rule;

class ImprovementController extends Controller
{
    private function plans(Request $request) {
        return PaymentPlan::whereIn('id',$request->user('client')->activePlanIds())
            ->whereIn('status',['active','paused'])->orderBy('plan_number')->get();
    }

    public function index(Request $request) {
        $plans=$this->plans($request);
        $improvements=Improvement::forAccount($request->user('client'))->with(['paymentPlan','latestUpdate'])
            ->latest('updated_at')->paginate(15);
        return view('improvements.index',compact('plans','improvements'));
    }

    public function store(Request $request) {
        $data=$request->validate([
            'payment_plan_id'=>['required','integer',Rule::in($this->plans($request)->modelKeys())],
            'title'=>['required','string','max:150'],
        ]+$this->rules(true));
        $improvement=$this->save($request,$data);
        return redirect()->route('portal.improvements.show',$improvement)->with('success','Your notification has been submitted. Admin will acknowledge receipt here.');
    }

    public function show(Request $request, Improvement $improvement) {
        $admin=$request->routeIs('admin.*');
        if (!$admin) $this->authorizeClient($request,$improvement);
        $improvement->load(['paymentPlan','client','updates'=>fn($q)=>$q->orderBy('id')]);
        $canUpdate=!$admin && in_array($improvement->paymentPlan->status,['active','paused'],true);

        $visibleNotes=fn($query)=>$query->when(!$admin,fn($query)=>$query->where('hidden_from_client',false));
        $improvement->load(['messageThread.messages'=>$visibleNotes,'messageThread.messages.attachments','updates.messageThread.messages'=>$visibleNotes,'updates.messageThread.messages.attachments']);
        $threads=$improvement->updates->pluck('messageThread')->filter();
        if ($improvement->messageThread) $threads->push($improvement->messageThread);
        foreach ($threads as $thread) {
            $readColumn=$admin?'admin_viewed_at':'client_viewed_at';
            $thread->messages()->whereIn('id',$thread->messages->modelKeys())
                ->where('sender_type',$admin?'client':'admin')->whereNull($readColumn)->update([$readColumn=>now()]);
            if ($admin) AdminNotice::where('secure_message_thread_id',$thread->id)->whereNull('dismissed_at')
                ->update(['dismissed_at'=>now(),'dismissed_by_user_id'=>$request->user()->id]);
        }
        return view('improvements.show',compact('improvement','admin','canUpdate'));

    }

    public function update(Request $request, Improvement $improvement) {
        $this->authorizeClient($request,$improvement);
        abort_unless(in_array($improvement->paymentPlan->status,['active','paused'],true),403);
        $data=$request->validate($this->rules(false));
        $this->save($request,$data,$improvement);
        return back()->with('success','Your update has been sent to admin.');
    }


    public function note(Request $request, Improvement $improvement) {
        $admin=$request->routeIs('admin.*');
        if (!$admin) $this->authorizeClient($request,$improvement);
        $data=$request->validate([
            'note'=>['required_without:attachments','nullable','string','max:2000'],
            'improvement_update_id'=>['nullable','integer'],
            'attachments'=>['nullable','array','max:5'],
            'attachments.*'=>['required','file','image','mimes:jpg,jpeg,png','mimetypes:image/jpeg,image/png','max:10240'],
        ]);
        $section=isset($data['improvement_update_id'])?$improvement->updates()->findOrFail($data['improvement_update_id']):null;
        abort_unless($section || $improvement->messageThread()->exists(),422);
        $context=$section
            ? (($improvement->updates()->min('id')==$section->id?'Original notification':'Progress update').' '.$section->created_at->format('M j, Y g:i A').' #'.$section->id)
            : 'General improvement notes';
        $thread=DB::transaction(function() use($request,$improvement,$admin,$data,$section,$context) {
            // Serialize first-note creation so both participants share one thread.
            $locked=Improvement::whereKey($improvement->id)->lockForUpdate()->firstOrFail();
            $thread=\App\Models\SecureMessageThread::firstOrCreate([
                'improvement_id'=>$locked->id,'improvement_update_id'=>$section?->id,
            ],[
                'client_id'=>$locked->client_id,'payment_plan_id'=>$locked->payment_plan_id,
                'subject'=>\Illuminate\Support\Str::limit('Improvement: '.$locked->title,75,'').' - '.$context,
                'category'=>'general','latest_message_at'=>now(),
            ]);
            $message=$thread->messages()->create([
                'sender_type'=>$admin?'admin':'client',
                'sender_user_id'=>$admin?$request->user()->id:null,
                'sender_client_id'=>$admin?null:$locked->client_id,
                'body'=>$data['note']??'',
            ]);
            app(\App\Services\SecureMessageFileService::class)->attach($message,$request->file('attachments',[]),[],[],[]);
            $thread->update(['latest_message_at'=>now()]);
            if (!$admin) AdminNotice::create([
                'type'=>'secure_message_reply','client_id'=>$locked->client_id,
                'payment_plan_id'=>$locked->payment_plan_id,'secure_message_thread_id'=>$thread->id,
                'title'=>'New improvement note',
                'message'=>$request->user('client')->displayName().' added a note to '.$locked->title.' ('.$context.').',
            ]);
            return $thread;
        });
        $status='Note sent.';
        if ($admin) {
            $sent=app(\App\Services\SecureMessageNotificationService::class)->send($thread->load('client'));
            $status.=$sent?' Email notification sent.':' Email notification was not sent.';
        }
        return redirect()->to(route($admin?'admin.improvements.show':'portal.improvements.show',$improvement).$thread->notesAnchor())
            ->with('success',$status);
    }


    public function moderateNote(Request $request, Improvement $improvement, \App\Models\SecureMessage $message) {
        $thread=$message->thread;
        abort_unless($thread && $thread->improvement_id===$improvement->id,404);
        if ($request->isMethod('delete')) {
            DB::transaction(function() use($message,$thread) {
                $thread->newQuery()->whereKey($thread->id)->lockForUpdate()->firstOrFail();
                foreach ($message->attachments as $attachment) {
                    abort_unless(app(\App\Services\SecureMessageFileService::class)->deleteAttachmentFile($attachment),500,'Photo could not be deleted.');
                }
                $message->delete();
                $thread->update(['latest_message_at'=>$thread->messages()->max('created_at') ?? $thread->created_at]);
            });
            $status='Note deleted.';
        } else {
            $data=$request->validate(['hidden_from_client'=>['required','boolean']]);
            $message->update(['hidden_from_client'=>$data['hidden_from_client']]);
            $status=$message->hidden_from_client?'Note hidden from client.':'Note visible to client.';
        }
        return redirect()->to(route('admin.improvements.show',$improvement).$thread->notesAnchor())->with('success',$status);
    }

    private function rules(bool $initial): array {


        return [
            'body'=>[$initial?'required':'required_without:photos','nullable','string','max:10000'],
            'photos'=>['nullable','array','max:5'],
            'photos.*'=>['required','file','image','mimes:jpg,jpeg,png','mimetypes:image/jpeg,image/png','max:10240'],
        ];
    }

    private function save(Request $request,array $data,?Improvement $improvement=null): Improvement {
        $photos=[];
        try {
            foreach ($request->file('photos',[]) as $file) $photos[]=app(SharedDocumentStorageService::class)->store($file);
            return DB::transaction(function() use($request,$data,$improvement,$photos) {
                $initial=$improvement===null;
                $improvement ??= Improvement::create([
                    'client_id'=>$request->user('client')->client_id,
                    'payment_plan_id'=>$data['payment_plan_id'],'title'=>$data['title'],
                ]);
                $update=$improvement->updates()->create(['body'=>$data['body']??null,'photos'=>$photos]);
                $improvement->touch();
                AdminNotice::create([
                    'type'=>'improvement_updated','improvement_update_id'=>$update->id,
                    'client_id'=>$improvement->client_id,'payment_plan_id'=>$improvement->payment_plan_id,
                    'title'=>$initial?'Planned improvement notification':'Improvement update',
                    'message'=>$request->user('client')->displayName().' '.($initial?'notified you of ':'updated ').$improvement->title
                        .(count($photos)?' with '.count($photos).' photo(s).':'.'),
                ]);
                return $improvement;
            });
        } catch (\Throwable $e) {
            foreach ($photos as $photo) Storage::disk($photo['disk'])->delete($photo['path']);
            throw $e;
        }
    }

    public function acknowledge(Request $request, Improvement $improvement, ImprovementUpdate $update) {
        abort_unless($update->improvement_id===$improvement->id,404);
        DB::transaction(function() use($request,$update) {
            $locked=ImprovementUpdate::lockForUpdate()->findOrFail($update->id);
            if (!$locked->received_at) $locked->update(['received_at'=>now(),'received_by_user_id'=>$request->user()->id]);
            AdminNotice::where('improvement_update_id',$update->id)->whereNull('dismissed_at')
                ->update(['dismissed_at'=>now(),'dismissed_by_user_id'=>$request->user()->id]);
        });
        return back()->with('success','Receipt acknowledged.');
    }

    public function photo(Request $request, Improvement $improvement, ImprovementUpdate $update, string $photo) {
        if (!$request->routeIs('admin.*')) $this->authorizeClient($request,$improvement);
        abort_unless($update->improvement_id===$improvement->id && ctype_digit($photo),404);
        $file=$update->photos[(int)$photo]??null;
        abort_unless($file && Storage::disk($file['disk'])->exists($file['path']),404);
        return response()->file(Storage::disk($file['disk'])->path($file['path']),[
            'Content-Type'=>$file['mime'],'X-Content-Type-Options'=>'nosniff','Cache-Control'=>'private, no-store',
        ]);
    }

    private function authorizeClient(Request $request, Improvement $improvement): void {
        abort_unless(Improvement::forAccount($request->user('client'))->whereKey($improvement->id)->exists(),404);
    }
}
