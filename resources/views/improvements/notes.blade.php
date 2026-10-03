@php($notesId=$noteSection?'notes-'.$noteSection->id:'notes')
<section class="{{$noteSection?'border-top mt-3 pt-3':'admin-next-card mt-4 p-3'}}" id="{{$notesId}}" aria-labelledby="{{$notesId}}-heading">
<div class="d-flex flex-wrap align-items-center gap-2"><h2 class="h5 mb-0" id="{{$notesId}}-heading">{{$noteSection?'Notes / Reply':'General improvement notes'}}</h2></div>
@if($notes->count()>3)
<details class="mt-2"><summary class="small" style="cursor:pointer">Earlier notes ({{$notes->count()-3}})</summary>
@foreach($notes->slice(0,-3) as $note)@include('improvements.note')@endforeach
</details>
@endif
@foreach($notes->take(-3) as $note)@include('improvements.note')@endforeach
<form class="mt-2" method="post" action="{{route($admin?'admin.improvements.notes.store':'portal.improvements.notes.store',$improvement)}}">@csrf
@if($noteSection)<input type="hidden" name="improvement_update_id" value="{{$noteSection->id}}">@endif
<label class="visually-hidden" for="{{$notesId}}-input">Write a note</label>
<textarea class="form-control form-control-sm" id="{{$notesId}}-input" name="note" rows="2" maxlength="2000" placeholder="Write a short note..." required>{{(string)old('improvement_update_id')===(string)$noteSection?->id?old('note'):''}}</textarea>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2"><small class="text-muted">Notes do not change receipt acknowledgment.</small><button class="btn btn-sm btn-outline-brand">Send note</button></div>
</form>
</section>
