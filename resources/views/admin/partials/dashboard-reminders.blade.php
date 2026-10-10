@if($preview=session('admin_reminder_test.'.auth()->id()))
<div class="admin-next-card mb-4">
 <h2>Admin Reminder <span class="badge bg-secondary">TEST</span></h2>
 <div class="amendment-entry"><div><strong>{{$preview['title']}}</strong>
  @if($preview['message'])<p class="mb-0" style="white-space: pre-wrap;">{{$preview['message']}}</p>@endif
 </div><div class="d-flex gap-2 flex-shrink-0">
  @if($preview['url'])<a class="btn btn-sm btn-brand" href="{{$preview['url']}}">Open {{$preview['destination']}}</a>@endif
  <form method="post" action="{{route('admin.reminders.test-dismiss')}}">@csrf<button class="btn btn-sm btn-outline-brand">Dismiss test</button></form>
 </div></div>
</div>
@endif
@if($adminReminders->isNotEmpty())
<div class="admin-next-card mb-4">
 <div class="d-flex align-items-center gap-3 mb-2"><h2 class="mb-0">Admin Reminders</h2><span class="dashboard-status status-due">{{$adminReminders->count()}} due</span></div>
 @foreach($adminReminders as $occurrence)
 <div class="amendment-entry"><div><strong>{{$occurrence->reminder->title}}</strong>
  @if($occurrence->reminder->message)<p class="mb-0" style="white-space: pre-wrap;">{{$occurrence->reminder->message}}</p>@endif
 </div><div class="d-flex gap-2 flex-shrink-0">
  @if($occurrence->reminder->destinationUrl())<a class="btn btn-sm btn-brand" href="{{$occurrence->reminder->destinationUrl()}}">Open {{$occurrence->reminder->destinationLabel()}}</a>@endif
  <form method="post" action="{{route('admin.reminders.dismiss',$occurrence)}}">@csrf<button class="btn btn-sm btn-outline-brand">Dismiss</button></form>
 </div></div>
 @endforeach
</div>
@endif
