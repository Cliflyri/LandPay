@php($notesId=$noteSection?'notes-'.$noteSection->id:'notes')
<section class="{{$noteSection?'border-top mt-2 pt-1':'admin-next-card mt-4 p-3'}}" id="{{$notesId}}" aria-labelledby="{{$notesId}}-heading">
@if($notes->count()>3)
<details class="mt-2"><summary class="small" style="cursor:pointer">Earlier notes ({{$notes->count()-3}})</summary>
@foreach($notes->slice(0,-3) as $note)@include('improvements.note')@endforeach
</details>
@endif
@foreach($notes->take(-3) as $note)@include('improvements.note')@endforeach
<form class="mt-2" method="post" enctype="multipart/form-data" action="{{route($admin?'admin.improvements.notes.store':'portal.improvements.notes.store',$improvement)}}">@csrf
@if($noteSection)<input type="hidden" name="improvement_update_id" value="{{$noteSection->id}}">@endif
<label class="h5 d-block mb-2" id="{{$notesId}}-heading" for="{{$notesId}}-input">{{$noteSection?'Notes / Reply':'General improvement notes / Reply'}}</label>
<textarea class="form-control form-control-sm" id="{{$notesId}}-input" name="note" rows="2" maxlength="2000" placeholder="Write a short note, or attach a photo...">{{(string)old('improvement_update_id')===(string)$noteSection?->id?old('note'):''}}</textarea>
<details class="mt-2">
<summary class="small" style="cursor:pointer">Attach photos</summary>
@include('improvements.photo-picker',['pickerId'=>$notesId.'-photos','photoField'=>'attachments'])
<small class="text-muted">Text is optional when attaching photos.</small>
</details>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2"><small class="text-muted">Notes do not change receipt acknowledgment.</small><button class="btn btn-sm btn-outline-brand">Send note</button></div>
</form>
</section>
