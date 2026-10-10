@extends('layouts.admin')
@section('title',($reminder->exists?'Edit':'New').' Admin Reminder | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="container-fluid dashboard-container">
<div class="admin-heading"><div><span class="eyebrow eyebrow-dark">Admin Actions</span><h1>{{$reminder->exists?'Edit Admin Reminder':'New Admin Reminder'}}</h1><p class="mb-0">Create an operational reminder for administrators.</p></div></div>
@if($errors->any())<div class="alert alert-danger mt-4">{{$errors->first()}}</div>@endif
@if(session('success'))<div class="alert alert-success mt-4">{{session('success')}}</div>@endif
@if(session('error'))<div class="alert alert-danger mt-4">{{session('error')}}</div>@endif
<form class="admin-next-card mt-4 row g-3" method="post" action="{{$reminder->exists?route('admin.reminders.update',$reminder):route('admin.reminders.store')}}">@csrf @if($reminder->exists)@method('put')@endif
<div class="col-md-8"><label class="form-label" for="title">Title</label><input class="form-control" id="title" name="title" maxlength="150" required value="{{old('title',$reminder->title)}}"></div>
<div class="col-12"><label class="form-label" for="message">Instructions <span class="text-muted">(optional)</span></label><textarea class="form-control" id="message" name="message" rows="3" maxlength="1000">{{old('message',$reminder->message)}}</textarea></div>
<div class="col-md-4"><label class="form-label" for="recurrence_type">Repeat</label><select class="form-select" id="recurrence_type" name="recurrence_type">@foreach(['weekly'=>'Weekly','monthly'=>'Monthly','annually'=>'Annually'] as $value=>$label)<option value="{{$value}}" @selected(old('recurrence_type',$reminder->recurrence_type??'monthly')===$value)>{{$label}}</option>@endforeach</select></div>
<div class="col-md-4" data-reminder-frequency="weekly"><label class="form-label" for="day_of_week">Day of Week</label><select class="form-select" id="day_of_week" name="day_of_week">@foreach([1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday',7=>'Sunday'] as $value=>$label)<option value="{{$value}}" @selected((int)old('day_of_week',$reminder->day_of_week??1)===$value)>{{$label}}</option>@endforeach</select></div>
<div class="col-md-4" data-reminder-frequency="annually"><label class="form-label" for="month_of_year">Month</label><select class="form-select" id="month_of_year" name="month_of_year">@for($month=1;$month<=12;$month++)<option value="{{$month}}" @selected((int)old('month_of_year',$reminder->month_of_year??1)===$month)>{{\Illuminate\Support\Carbon::create(2000,$month,1)->format('F')}}</option>@endfor</select></div>
<div class="col-md-4" data-reminder-frequency="monthly annually"><label class="form-label" for="day_of_month">Day of Month</label><input class="form-control" type="number" min="1" max="31" id="day_of_month" name="day_of_month" required value="{{old('day_of_month',$reminder->day_of_month?:1)}}"><div class="form-text">Uses the final day in shorter months.</div></div>
<div class="col-md-4"><label class="form-label" for="display_time">Display Time</label><input class="form-control" type="time" id="display_time" name="display_time" required value="{{old('display_time',$reminder->display_time?substr($reminder->display_time,0,5):'08:00')}}"><div class="form-text">{{config('app.timezone')}}</div></div>
<div class="col-md-4"><label class="form-label" for="destination">Destination</label><select class="form-select" id="destination" name="destination">@foreach(\App\Models\AdminReminder::DESTINATIONS as $value=>$label)<option value="{{$value}}" @selected(old('destination',$reminder->destination?:'notification_only')===$value)>{{$label}}</option>@endforeach</select></div>
<div class="col-md-6"><div class="form-check form-switch"><input type="hidden" name="send_email" value="0"><input class="form-check-input" type="checkbox" id="send_email" name="send_email" value="1" @checked(old('send_email',$reminder->send_email))><label class="form-check-label" for="send_email">Send administrator email when due</label></div><div class="form-text">* Email also requires Scheduled reminders to be enabled in <a href="{{ route('admin.settings.index', ['section' => 'notifications']) }}">Admin Notifications</a>.</div></div>
<div class="col-md-6"><div class="form-check form-switch"><input type="hidden" name="active" value="0"><input class="form-check-input" type="checkbox" id="active" name="active" value="1" @checked(old('active',$reminder->exists?$reminder->active:true))><label class="form-check-label" for="active">Active</label></div></div>
@if($reminder->exists)
<div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" id="reset_status" name="reset_status" value="1"><label class="form-check-label" for="reset_status">Reset reminder so it can run again</label></div><div class="form-text">On save, clears this period's occurrence and dismissals. An active reminder can run again at the saved date and time; if already due, on the next scheduler run. Normal email settings still apply.</div></div>
@endif
<div class="col-12"><button class="btn btn-brand">{{$reminder->exists?'Save Reminder':'Create Reminder'}}</button> <a class="btn btn-outline-brand" href="{{route('admin.reminders.index')}}">Cancel</a></div>
<div class="col-12"><button class="btn btn-outline-brand" type="submit" name="intent" value="test" formnovalidate>Send test reminder</button> <a href="{{route('admin.dashboard')}}" target="_blank" rel="noopener">View dashboard preview</a><div class="form-text">Tests the current title, instructions and destination without saving or resetting. Sends to the configured admin email even if scheduled delivery is disabled, and replaces your session's dismissible TEST preview. Testing does not enable scheduled delivery.</div></div>
</form></div></section>
<script>
(function(){
 const frequency=document.getElementById('recurrence_type');
 function updateFields(){
  document.querySelectorAll('[data-reminder-frequency]').forEach(function(group){
   const visible=group.dataset.reminderFrequency.split(' ').includes(frequency.value);
   group.hidden=!visible;
   group.querySelectorAll('input,select').forEach(function(input){input.disabled=!visible;});
  });
 }
 frequency.addEventListener('change',updateFields);updateFields();
})();
</script>
@endsection
