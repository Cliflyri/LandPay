@extends($admin?'layouts.admin':'layouts.app')
@section('title',$improvement->title.' | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="{{$admin?'container-fluid dashboard-container px-2':'container site-container'}}">
<div class="admin-heading d-flex flex-wrap justify-content-between align-items-end gap-3"><div style="min-width:0;overflow-wrap:anywhere"><span class="eyebrow eyebrow-dark">Improvement notification</span><h1>{{$improvement->title}}</h1><p>{{$improvement->paymentPlan->plan_number}} &middot; {{$improvement->paymentPlan->title}}@if($admin)<br>{{$improvement->client->organization_name ?: trim($improvement->client->first_name.' '.$improvement->client->last_name)}}@endif</p></div>
<a class="btn btn-outline-brand" href="{{$admin?route('admin.plans.show',$improvement->paymentPlan).'#improvements':route('portal.improvements.index')}}">{{$admin?'Back to plan':'My Improvements'}}</a></div>
@if(session('success'))<div class="alert alert-success" role="status">{{session('success')}}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{$error}}</li>@endforeach</ul></div>@endif
@if($improvement->recorded_by_user_id)
<p class="small text-muted">Recorded by {{$improvement->recordedBy?->name ?? 'admin'}} on behalf of {{$improvement->client->organization_name ?: trim($improvement->client->first_name.' '.$improvement->client->last_name)}}</p>
@endif
<p class="text-muted">Receipt acknowledgment records your notification; it is not an approval process.</p>
@foreach($improvement->updates as $update)
<article class="admin-next-card mt-4" id="update-{{$update->id}}">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2"><h2 class="mb-0">{{$loop->first?'Planned improvement':'Progress update'}}</h2>@include('improvements.badge',['update'=>$update])</div>
<small class="text-muted">@if($loop->first && $improvement->recorded_by_user_id)Admin notified on {{$update->created_at->format('M j, Y')}}@else Submitted {{$update->created_at->format('M j, Y g:i A')}}@endif</small>
@if($update->body)<p class="mt-3" style="white-space:pre-wrap;overflow-wrap:anywhere">{{$update->body}}</p>@endif
@if($update->photos)<div class="row g-3 mt-1">
@foreach($update->photos as $photo)
@php($photoUrl=route($admin?'admin.improvements.photo':'portal.improvements.photo',[$improvement,$update,$loop->index]))
<div class="col-6 col-md-4"><a href="{{$photoUrl}}" target="_blank" rel="noopener"><img src="{{$photoUrl}}" alt="{{$photo['name']}}" loading="lazy" class="img-fluid rounded w-100" style="height:180px;object-fit:cover"><span class="d-block small text-break">{{$photo['name']}}</span></a></div>
@endforeach
</div>@endif
@if($admin && !$update->received_at)
<form class="mt-3" method="post" action="{{route('admin.improvements.acknowledge',[$improvement,$update])}}">@csrf<button class="btn btn-brand">Acknowledge receipt</button></form>
@endif
@include('improvements.notes',['noteSection'=>$update,'notes'=>$update->messageThread?->messages ?? collect()])
</article>
@endforeach
@if($canUpdate)
<div class="admin-next-card mt-4"><h2>Add photos or update</h2>
<form method="post" action="{{route('portal.improvements.update',$improvement)}}" enctype="multipart/form-data">@csrf
<label class="form-label" for="body">Progress or changes to your plans</label><textarea class="form-control" id="body" name="body" rows="3" maxlength="10000">{{old('body')}}</textarea>
@include('improvements.photos-input')
<p class="small text-muted mt-2">Add a note, photos, or both. Admin will be notified of each update.</p>
<button class="btn btn-brand mt-2">Send update to admin</button>
</form></div>
@endif
@if($improvement->messageThread)
@include('improvements.notes',['noteSection'=>null,'notes'=>$improvement->messageThread->messages])
@endif
</div></section>
@include('shared.secure-message-image-modal')
@endsection
