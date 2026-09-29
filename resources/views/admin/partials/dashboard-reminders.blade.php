@if($adminReminders->isNotEmpty())
<div class="admin-next-card mb-4">
 <div class="d-flex align-items-center gap-3 mb-2"><h2 class="mb-0">Admin Reminders</h2><span class="dashboard-status status-due">{{$adminReminders->count()}} due</span></div>
 @foreach($adminReminders as $occurrence)
 <div class="amendment-entry"><div><strong>{{$occurrence->reminder->title}}</strong>
  @if($occurrence->reminder->message)<p class="mb-0">{{$occurrence->reminder->message}}</p>@endif
 </div><div class="d-flex gap-2 flex-shrink-0">
  <a class="btn btn-sm btn-brand" href="{{$occurrence->reminder->destinationUrl()}}">Open {{$occurrence->reminder->destinationLabel()}}</a>
  <form method="post" action="{{route('admin.reminders.dismiss',$occurrence)}}">@csrf<button class="btn btn-sm btn-outline-brand">Dismiss for {{now()->format('F')}}</button></form>
 </div></div>
 @endforeach
</div>
@endif
