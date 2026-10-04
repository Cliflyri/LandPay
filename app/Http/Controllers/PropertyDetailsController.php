<?php
namespace App\Http\Controllers;
use App\Models\PaymentPlan;
use App\Services\SharedDocumentStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PropertyDetailsController extends Controller {
 public function edit(PaymentPlan $plan) { return view('property.edit',compact('plan')); }
 public function update(Request $request,PaymentPlan $plan) {
  $request->validate(['property_coordinates'=>['nullable','string','max:150']]);
  if ($request->filled('property_coordinates')) {
   $pair=trim($request->input('property_coordinates'));
   if (!preg_match('/^([+-]?\d+(?:\.\d+)?\s*\x{00B0}?\s*[NS])[\s,]+([+-]?\d+(?:\.\d+)?\s*\x{00B0}?\s*[EW])$/iu',$pair,$parts)
       && !preg_match('/^([+-]?\d+(?:\.\d+)?)\s*,\s*([+-]?\d+(?:\.\d+)?)$/u',$pair,$parts)) {
    throw ValidationException::withMessages(['property_coordinates'=>'Enter coordinates such as 35.674744 N 114.156267 W or 35.674744, -114.156267.']);
   }
   $request->merge(['property_latitude'=>$parts[1],'property_longitude'=>$parts[2]]);
  }
  foreach (['property_latitude'=>'NS','property_longitude'=>'EW'] as $field=>$directions) {
   $value=$request->input($field);
   if (is_string($value) && preg_match('/^([+-]?\d+(?:\.\d+)?)\s*\x{00B0}?\s*(['.$directions.'])?$/iu',trim($value),$parts)) {
    $number=(float)$parts[1];
    if (!empty($parts[2])) {
     $negative=in_array(strtoupper($parts[2]),['S','W'],true);
     if ($number<0 && !$negative) throw ValidationException::withMessages([$field=>'The negative sign conflicts with the N or E direction.']);
     $number=abs($number)*($negative?-1:1);
    }
    $request->merge([$field=>$number]);
   }
  }
  $ids=array_column($plan->property_photos??[],'id');
  $data=$request->validate([
   'property_latitude'=>['nullable','required_with:property_longitude','numeric','between:-90,90'],
   'property_longitude'=>['nullable','required_with:property_latitude','numeric','between:-180,180'],
   'property_notes'=>['nullable','string','max:10000'],
   'photos'=>['nullable','array','max:5'],
   'photos.*'=>['required','file','image','mimes:jpg,jpeg,png','mimetypes:image/jpeg,image/png','max:10240'],
   'remove'=>['nullable','array'],'remove.*'=>['string',Rule::in($ids)],
   'order'=>['nullable','array'],'order.*'=>['required','integer','min:1','max:1000'],
  ]);
  $uploaded=[];
  try {
   foreach($request->file('photos',[]) as $file) $uploaded[]=app(SharedDocumentStorageService::class)->store($file)+['id'=>(string)Str::uuid()];
   $removed=DB::transaction(function() use($plan,$data,$uploaded) {
    $locked=PaymentPlan::whereKey($plan->id)->lockForUpdate()->firstOrFail();
    $current=collect($locked->property_photos??[]);
    $removed=$current->whereIn('id',$data['remove']??[])->values();
    $kept=$current->whereNotIn('id',$data['remove']??[])->sortBy(fn($photo)=>$data['order'][$photo['id']]??1000)->values();
    if($kept->count()+count($uploaded)>20) throw ValidationException::withMessages(['photos'=>'A property can have up to 20 photos. Remove some before adding more.']);
    $locked->update([
     'property_latitude'=>$data['property_latitude']??null,'property_longitude'=>$data['property_longitude']??null,
     'property_notes'=>$data['property_notes']??null,'property_photos'=>$kept->concat($uploaded)->all(),
    ]);
    return $removed;
   });
  } catch(\Throwable $e) {
   foreach($uploaded as $photo) Storage::disk($photo['disk'])->delete($photo['path']);
   throw $e;
  }
  foreach($removed as $photo) Storage::disk($photo['disk'])->delete($photo['path']);
  return redirect()->to(route('admin.plans.show',$plan).'#property-details-'.$plan->id)->with('success','Property details saved.');
 }
 public function photo(Request $request,PaymentPlan $plan,string $photo) {
  if(!$request->routeIs('admin.*')) abort_unless(in_array($plan->id,$request->user('client')->activePlanIds(),true),404);
  $file=collect($plan->property_photos??[])->firstWhere('id',$photo);
  abort_unless($file && Storage::disk($file['disk'])->exists($file['path']),404);
  return response()->file(Storage::disk($file['disk'])->path($file['path']),[
   'Content-Type'=>$file['mime'],'X-Content-Type-Options'=>'nosniff','Cache-Control'=>'private, no-store',
  ]);
 }
}
